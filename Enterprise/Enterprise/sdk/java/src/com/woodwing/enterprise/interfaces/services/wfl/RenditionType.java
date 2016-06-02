/**
 * RenditionType.java
 *
 * This file was auto-generated from WSDL
 * by the Apache Axis 1.4 Apr 22, 2006 (06:55:48 PDT) WSDL2Java emitter.
 */

package com.woodwing.enterprise.interfaces.services.wfl;

public class RenditionType implements java.io.Serializable {
    private java.lang.String _value_;
    private static java.util.HashMap _table_ = new java.util.HashMap();

    // Constructor
    protected RenditionType(java.lang.String value) {
        _value_ = value;
        _table_.put(_value_,this);
    }

    public static final java.lang.String _value1 = "none";
    public static final java.lang.String _value2 = "thumb";
    public static final java.lang.String _value3 = "preview";
    public static final java.lang.String _value4 = "placement";
    public static final java.lang.String _value5 = "native";
    public static final java.lang.String _value6 = "output";
    public static final java.lang.String _value7 = "trailer";
    public static final RenditionType value1 = new RenditionType(_value1);
    public static final RenditionType value2 = new RenditionType(_value2);
    public static final RenditionType value3 = new RenditionType(_value3);
    public static final RenditionType value4 = new RenditionType(_value4);
    public static final RenditionType value5 = new RenditionType(_value5);
    public static final RenditionType value6 = new RenditionType(_value6);
    public static final RenditionType value7 = new RenditionType(_value7);
    public java.lang.String getValue() { return _value_;}
    public static RenditionType fromValue(java.lang.String value)
          throws java.lang.IllegalArgumentException {
        RenditionType enumeration = (RenditionType)
            _table_.get(value);
        if (enumeration==null) throw new java.lang.IllegalArgumentException();
        return enumeration;
    }
    public static RenditionType fromString(java.lang.String value)
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
        new org.apache.axis.description.TypeDesc(RenditionType.class);

    static {
        typeDesc.setXmlType(new javax.xml.namespace.QName("urn:SmartConnection", "RenditionType"));
    }
    /**
     * Return type metadata object
     */
    public static org.apache.axis.description.TypeDesc getTypeDesc() {
        return typeDesc;
    }

}
