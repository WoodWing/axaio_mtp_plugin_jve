/**
 * RelationType.java
 *
 * This file was auto-generated from WSDL
 * by the Apache Axis 1.4 Apr 22, 2006 (06:55:48 PDT) WSDL2Java emitter.
 */

package com.woodwing.enterprise.interfaces.services.wfl;

public class RelationType implements java.io.Serializable {
    private java.lang.String _value_;
    private static java.util.HashMap _table_ = new java.util.HashMap();

    // Constructor
    protected RelationType(java.lang.String value) {
        _value_ = value;
        _table_.put(_value_,this);
    }

    public static final java.lang.String _Placed = "Placed";
    public static final java.lang.String _Planned = "Planned";
    public static final java.lang.String _Candidate = "Candidate";
    public static final java.lang.String _Contained = "Contained";
    public static final java.lang.String _Related = "Related";
    public static final java.lang.String _InstanceOf = "InstanceOf";
    public static final RelationType Placed = new RelationType(_Placed);
    public static final RelationType Planned = new RelationType(_Planned);
    public static final RelationType Candidate = new RelationType(_Candidate);
    public static final RelationType Contained = new RelationType(_Contained);
    public static final RelationType Related = new RelationType(_Related);
    public static final RelationType InstanceOf = new RelationType(_InstanceOf);
    public java.lang.String getValue() { return _value_;}
    public static RelationType fromValue(java.lang.String value)
          throws java.lang.IllegalArgumentException {
        RelationType enumeration = (RelationType)
            _table_.get(value);
        if (enumeration==null) throw new java.lang.IllegalArgumentException();
        return enumeration;
    }
    public static RelationType fromString(java.lang.String value)
          throws java.lang.IllegalArgumentException {
        return fromValue(value);
    }
    public boolean equals(java.lang.Object obj) {return (obj == this);}
    public int hashCode() { return toString().hashCode();}
    public java.lang.String toString() { return _value_;}
    public java.lang.Object readResolve() throws java.io.ObjectStreamException { return fromValue(_value_);}
    public static org.apache.axis.encoding.Serializer getSerializer(
           java.lang.String mechType, 
           java.lang.Class _javaType,  
           javax.xml.namespace.QName _xmlType) {
        return 
          new org.apache.axis.encoding.ser.EnumSerializer(
            _javaType, _xmlType);
    }
    public static org.apache.axis.encoding.Deserializer getDeserializer(
           java.lang.String mechType, 
           java.lang.Class _javaType,  
           javax.xml.namespace.QName _xmlType) {
        return 
          new org.apache.axis.encoding.ser.EnumDeserializer(
            _javaType, _xmlType);
    }
    // Type metadata
    private static org.apache.axis.description.TypeDesc typeDesc =
        new org.apache.axis.description.TypeDesc(RelationType.class);

    static {
        typeDesc.setXmlType(new javax.xml.namespace.QName("urn:SmartConnection", "RelationType"));
    }
    /**
     * Return type metadata object
     */
    public static org.apache.axis.description.TypeDesc getTypeDesc() {
        return typeDesc;
    }

}
