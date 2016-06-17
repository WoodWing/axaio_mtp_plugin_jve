/**
 * OperationType.java
 *
 * This file was auto-generated from WSDL
 * by the Apache Axis 1.4 Apr 22, 2006 (06:55:48 PDT) WSDL2Java emitter.
 */

package com.woodwing.enterprise.interfaces.services.dat;

public class OperationType implements java.io.Serializable {
    private java.lang.String _value_;
    private static java.util.HashMap _table_ = new java.util.HashMap();

    // Constructor
    protected OperationType(java.lang.String value) {
        _value_ = value;
        _table_.put(_value_,this);
    }

    public static final java.lang.String _value1 = "<";
    public static final java.lang.String _value2 = ">";
    public static final java.lang.String _value3 = "<=";
    public static final java.lang.String _value4 = ">=";
    public static final java.lang.String _value5 = "=";
    public static final java.lang.String _value6 = "!=";
    public static final java.lang.String _value7 = "contains";
    public static final java.lang.String _value8 = "starts";
    public static final java.lang.String _value9 = "ends";
    public static final java.lang.String _value10 = "within";
    public static final OperationType value1 = new OperationType(_value1);
    public static final OperationType value2 = new OperationType(_value2);
    public static final OperationType value3 = new OperationType(_value3);
    public static final OperationType value4 = new OperationType(_value4);
    public static final OperationType value5 = new OperationType(_value5);
    public static final OperationType value6 = new OperationType(_value6);
    public static final OperationType value7 = new OperationType(_value7);
    public static final OperationType value8 = new OperationType(_value8);
    public static final OperationType value9 = new OperationType(_value9);
    public static final OperationType value10 = new OperationType(_value10);
    public java.lang.String getValue() { return _value_;}
    public static OperationType fromValue(java.lang.String value)
          throws java.lang.IllegalArgumentException {
        OperationType enumeration = (OperationType)
            _table_.get(value);
        if (enumeration==null) throw new java.lang.IllegalArgumentException();
        return enumeration;
    }
    public static OperationType fromString(java.lang.String value)
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
        new org.apache.axis.description.TypeDesc(OperationType.class);

    static {
        typeDesc.setXmlType(new javax.xml.namespace.QName("urn:PlutusDatasource", "OperationType"));
    }
    /**
     * Return type metadata object
     */
    public static org.apache.axis.description.TypeDesc getTypeDesc() {
        return typeDesc;
    }

}
