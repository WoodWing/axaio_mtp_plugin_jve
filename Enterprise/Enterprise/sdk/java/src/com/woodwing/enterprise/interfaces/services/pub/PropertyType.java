/**
 * PropertyType.java
 *
 * This file was auto-generated from WSDL
 * by the Apache Axis 1.4 Apr 22, 2006 (06:55:48 PDT) WSDL2Java emitter.
 */

package com.woodwing.enterprise.interfaces.services.pub;

public class PropertyType implements java.io.Serializable {
    private java.lang.String _value_;
    private static java.util.HashMap _table_ = new java.util.HashMap();

    // Constructor
    protected PropertyType(java.lang.String value) {
        _value_ = value;
        _table_.put(_value_,this);
    }

    public static final java.lang.String _value1 = "string";
    public static final java.lang.String _value2 = "multistring";
    public static final java.lang.String _value3 = "multiline";
    public static final java.lang.String _value4 = "bool";
    public static final java.lang.String _value5 = "int";
    public static final java.lang.String _value6 = "double";
    public static final java.lang.String _value7 = "date";
    public static final java.lang.String _value8 = "datetime";
    public static final java.lang.String _value9 = "list";
    public static final java.lang.String _value10 = "multilist";
    public static final java.lang.String _value11 = "fileselector";
    public static final java.lang.String _value12 = "file";
    public static final java.lang.String _value13 = "articlecomponentselector";
    public static final java.lang.String _value14 = "articlecomponent";
    public static final PropertyType value1 = new PropertyType(_value1);
    public static final PropertyType value2 = new PropertyType(_value2);
    public static final PropertyType value3 = new PropertyType(_value3);
    public static final PropertyType value4 = new PropertyType(_value4);
    public static final PropertyType value5 = new PropertyType(_value5);
    public static final PropertyType value6 = new PropertyType(_value6);
    public static final PropertyType value7 = new PropertyType(_value7);
    public static final PropertyType value8 = new PropertyType(_value8);
    public static final PropertyType value9 = new PropertyType(_value9);
    public static final PropertyType value10 = new PropertyType(_value10);
    public static final PropertyType value11 = new PropertyType(_value11);
    public static final PropertyType value12 = new PropertyType(_value12);
    public static final PropertyType value13 = new PropertyType(_value13);
    public static final PropertyType value14 = new PropertyType(_value14);
    public java.lang.String getValue() { return _value_;}
    public static PropertyType fromValue(java.lang.String value)
          throws java.lang.IllegalArgumentException {
        PropertyType enumeration = (PropertyType)
            _table_.get(value);
        if (enumeration==null) throw new java.lang.IllegalArgumentException();
        return enumeration;
    }
    public static PropertyType fromString(java.lang.String value)
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
        new org.apache.axis.description.TypeDesc(PropertyType.class);

    static {
        typeDesc.setXmlType(new javax.xml.namespace.QName("urn:EnterprisePublishing", "PropertyType"));
    }
    /**
     * Return type metadata object
     */
    public static org.apache.axis.description.TypeDesc getTypeDesc() {
        return typeDesc;
    }

}
